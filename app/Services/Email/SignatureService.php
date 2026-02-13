<?php
/**
 * Email Signature Service
 * 
 * Manages HTML email signatures for tenants
 */

namespace App\Services\Email;

use App\Core\Database;

class SignatureService
{
    private Database $db;

    public function __construct(Database $db)
    {
        $this->db = $db;
    }

    /**
     * Get signature for current tenant
     */
    public function get(): ?array
    {
        $tenantId = $this->db->getTenantId();

        return $this->db->fetch(
            "SELECT signature_html, signature_plain FROM email_settings WHERE tenant_id = ?",
            [$tenantId]
        );
    }

    /**
     * Save signature
     */
    public function save(string $html): bool
    {
        $tenantId = $this->db->getTenantId();

        $plain = strip_tags(str_replace(['<br>', '<br/>', '<br />'], "\n", $html));

        $this->db->query(
            "UPDATE email_settings SET signature_html = ?, signature_plain = ? WHERE tenant_id = ?",
            [$html, $plain, $tenantId]
        );

        return true;
    }

    /**
     * Apply signature to email body
     */
    public function applyToEmail(string $bodyHtml): string
    {
        $signature = $this->get();

        if (!$signature || empty($signature['signature_html'])) {
            return $bodyHtml;
        }

        // Add signature before closing body tag or at end
        $signatureHtml = '<div class="email-signature" style="margin-top: 20px; padding-top: 20px; border-top: 1px solid #eee;">'
            . $signature['signature_html']
            . '</div>';

        if (stripos($bodyHtml, '</body>') !== false) {
            return str_ireplace('</body>', $signatureHtml . '</body>', $bodyHtml);
        }

        return $bodyHtml . $signatureHtml;
    }

    /**
     * Generate signature HTML from structured data
     */
    public function generateFromData(array $data): string
    {
        $html = '<table cellpadding="0" cellspacing="0" border="0" style="font-family: Arial, sans-serif; font-size: 14px; color: #333;">';

        // Name and title
        if (!empty($data['name'])) {
            $html .= '<tr><td style="font-weight: bold; font-size: 16px; color: #1a1a1a;">' . htmlspecialchars($data['name']) . '</td></tr>';
        }
        if (!empty($data['title'])) {
            $html .= '<tr><td style="color: #666; font-size: 13px;">' . htmlspecialchars($data['title']) . '</td></tr>';
        }
        if (!empty($data['company'])) {
            $html .= '<tr><td style="font-weight: 500; margin-top: 5px;">' . htmlspecialchars($data['company']) . '</td></tr>';
        }

        $html .= '<tr><td style="height: 10px;"></td></tr>';

        // Contact info
        if (!empty($data['phone'])) {
            $html .= '<tr><td style="font-size: 13px;">📞 ' . htmlspecialchars($data['phone']) . '</td></tr>';
        }
        if (!empty($data['email'])) {
            $html .= '<tr><td style="font-size: 13px;">✉️ <a href="mailto:' . htmlspecialchars($data['email']) . '" style="color: #0066cc; text-decoration: none;">' . htmlspecialchars($data['email']) . '</a></td></tr>';
        }
        if (!empty($data['website'])) {
            $html .= '<tr><td style="font-size: 13px;">🌐 <a href="' . htmlspecialchars($data['website']) . '" style="color: #0066cc; text-decoration: none;">' . htmlspecialchars($data['website']) . '</a></td></tr>';
        }
        if (!empty($data['address'])) {
            $html .= '<tr><td style="font-size: 13px; color: #666;">📍 ' . htmlspecialchars($data['address']) . '</td></tr>';
        }

        // Social links
        if (!empty($data['linkedin']) || !empty($data['twitter']) || !empty($data['facebook'])) {
            $html .= '<tr><td style="height: 10px;"></td></tr>';
            $html .= '<tr><td>';

            if (!empty($data['linkedin'])) {
                $html .= '<a href="' . htmlspecialchars($data['linkedin']) . '" style="margin-right: 10px;">LinkedIn</a>';
            }
            if (!empty($data['twitter'])) {
                $html .= '<a href="' . htmlspecialchars($data['twitter']) . '" style="margin-right: 10px;">Twitter</a>';
            }
            if (!empty($data['facebook'])) {
                $html .= '<a href="' . htmlspecialchars($data['facebook']) . '">Facebook</a>';
            }

            $html .= '</td></tr>';
        }

        // Custom message
        if (!empty($data['custom_message'])) {
            $html .= '<tr><td style="height: 15px;"></td></tr>';
            $html .= '<tr><td style="font-size: 12px; color: #888; font-style: italic;">' . htmlspecialchars($data['custom_message']) . '</td></tr>';
        }

        $html .= '</table>';

        return $html;
    }
}
