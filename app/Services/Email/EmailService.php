<?php
/**
 * Email Service - Core email sending functionality
 * 
 * Handles SMTP configuration, template rendering, and email delivery
 * for multi-tenant email system.
 */

namespace App\Services\Email;

use App\Core\Database;

class EmailService
{
    private Database $db;
    private ?array $settings = null;
    private string $encryptionKey;

    public function __construct(Database $db)
    {
        $this->db = $db;
        // Use a tenant-specific encryption key derived from app key + tenant_id
        $this->encryptionKey = $_ENV['APP_KEY'] ?? 'buildflow-default-key-change-me';
    }

    /**
     * Get SMTP settings for current tenant
     */
    public function getSettings(): ?array
    {
        if ($this->settings !== null) {
            return $this->settings;
        }

        $tenantId = $this->db->getTenantId();
        if (!$tenantId) {
            return null;
        }

        $this->settings = $this->db->fetch(
            "SELECT * FROM email_settings WHERE tenant_id = ?",
            [$tenantId]
        );

        return $this->settings;
    }

    /**
     * Save SMTP settings (encrypts password)
     */
    public function saveSettings(array $data): array
    {
        $tenantId = $this->db->getTenantId();
        if (!$tenantId) {
            throw new \Exception('No tenant context');
        }

        $existing = $this->db->fetch(
            "SELECT id FROM email_settings WHERE tenant_id = ?",
            [$tenantId]
        );

        $saveData = [
            'smtp_host' => $data['smtp_host'] ?? '',
            'smtp_port' => (int) ($data['smtp_port'] ?? 587),
            'encryption' => $data['encryption'] ?? 'tls',
            'username' => $data['username'] ?? '',
            'sender_name' => $data['sender_name'] ?? '',
            'sender_email' => $data['sender_email'] ?? '',
            'reply_to_email' => $data['reply_to_email'] ?? '',
            'daily_limit' => (int) ($data['daily_limit'] ?? 100),
            'updated_at' => date('Y-m-d H:i:s'),
        ];

        // Only update password if provided
        if (!empty($data['password'])) {
            $saveData['password_encrypted'] = $this->encryptPassword($data['password'], $tenantId);
        }

        if ($existing) {
            $this->db->update('email_settings', $saveData, ['id' => $existing['id']]);
            $settingsId = $existing['id'];
        } else {
            // Only add tenant_id for INSERT (not update - Database handles tenancy)
            $saveData['tenant_id'] = $tenantId;
            $saveData['created_at'] = date('Y-m-d H:i:s');
            $settingsId = $this->db->insert('email_settings', $saveData);
        }

        // Clear cached settings
        $this->settings = null;

        return $this->getSettings();
    }

    /**
     * Test SMTP connection
     */
    public function testConnection(): array
    {
        $settings = $this->getSettings();
        if (!$settings || empty($settings['smtp_host'])) {
            return ['success' => false, 'error' => 'SMTP settings not configured'];
        }

        try {
            $password = $this->decryptPassword($settings['password_encrypted'], $settings['tenant_id']);

            // Try to establish SMTP connection
            $smtp = $this->createSmtpConnection($settings, $password);

            if ($smtp === true) {
                // Mark as verified - use 'id' since Database auto-adds tenant_id
                $this->db->update('email_settings', [
                    'is_verified' => true,
                    'verified_at' => date('Y-m-d H:i:s'),
                ], ['id' => $settings['id']]);

                return ['success' => true, 'message' => 'SMTP connection successful'];
            }

            return ['success' => false, 'error' => $smtp];
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Create SMTP connection for testing
     */
    private function createSmtpConnection(array $settings, string $password)
    {
        $host = $settings['smtp_host'];
        $port = (int) $settings['smtp_port'];
        $encryption = $settings['encryption'];
        $username = $settings['username'];

        // Build connection string
        $prefix = '';
        if ($encryption === 'ssl') {
            $prefix = 'ssl://';
        } elseif ($encryption === 'tls' || $encryption === 'starttls') {
            $prefix = 'tls://';
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client(
            $prefix . $host . ':' . $port,
            $errno,
            $errstr,
            10,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return "Connection failed: $errstr ($errno)";
        }

        // Read greeting
        $response = fgets($socket, 1024);
        if (strpos($response, '220') !== 0) {
            fclose($socket);
            return "Invalid server response: $response";
        }

        // EHLO
        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        $this->readSmtpResponse($socket);

        // Start TLS if needed
        if ($encryption === 'starttls') {
            fwrite($socket, "STARTTLS\r\n");
            $response = $this->readSmtpResponse($socket);
            if (strpos($response, '220') !== 0) {
                fclose($socket);
                return "STARTTLS failed: $response";
            }
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            fwrite($socket, "EHLO " . gethostname() . "\r\n");
            $this->readSmtpResponse($socket);
        }

        // AUTH LOGIN
        fwrite($socket, "AUTH LOGIN\r\n");
        $response = $this->readSmtpResponse($socket);
        if (strpos($response, '334') !== 0) {
            fclose($socket);
            return "AUTH not supported: $response";
        }

        fwrite($socket, base64_encode($username) . "\r\n");
        $response = $this->readSmtpResponse($socket);

        fwrite($socket, base64_encode($password) . "\r\n");
        $response = $this->readSmtpResponse($socket);

        if (strpos($response, '235') !== 0) {
            fclose($socket);
            return "Authentication failed: $response";
        }

        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return true;
    }

    /**
     * Read SMTP response
     */
    private function readSmtpResponse($socket): string
    {
        $response = '';
        while ($line = fgets($socket, 1024)) {
            $response .= $line;
            if (substr($line, 3, 1) === ' ') {
                break;
            }
        }
        return trim($response);
    }

    /**
     * Send email immediately
     */
    public function send(array $email): array
    {
        $settings = $this->getSettings();
        if (!$settings || !$settings['is_verified']) {
            return ['success' => false, 'error' => 'SMTP not configured or verified'];
        }

        // Check daily limit
        if (!$this->checkDailyLimit($settings)) {
            return ['success' => false, 'error' => 'Daily email limit reached'];
        }

        try {
            $password = $this->decryptPassword($settings['password_encrypted'], $settings['tenant_id']);

            // Build email headers and body
            $result = $this->sendViaSMTP($settings, $password, $email);

            if ($result['success']) {
                // Log the email
                $this->logEmail($email, 'sent', $result['message_id']);

                // Update daily count
                $this->incrementDailyCount($settings['tenant_id']);
            } else {
                $this->logEmail($email, 'failed', null, $result['error']);
            }

            return $result;
        } catch (\Exception $e) {
            $this->logEmail($email, 'failed', null, $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Queue email for background sending
     */
    public function queue(array $email, ?string $scheduledAt = null): int
    {
        $tenantId = $this->db->getTenantId();

        $queueData = [
            'tenant_id' => $tenantId,
            'to_addresses' => json_encode($email['to'] ?? []),
            'cc_addresses' => json_encode($email['cc'] ?? []),
            'bcc_addresses' => json_encode($email['bcc'] ?? []),
            'subject' => $email['subject'] ?? '',
            'body_html' => $email['body_html'] ?? '',
            'body_plain' => $email['body_plain'] ?? strip_tags($email['body_html'] ?? ''),
            'attachments' => json_encode($email['attachments'] ?? []),
            'template_id' => $email['template_id'] ?? null,
            'context_type' => $email['context_type'] ?? null,
            'context_id' => $email['context_id'] ?? null,
            'priority' => $email['priority'] ?? 5,
            'status' => 'pending',
            'scheduled_at' => $scheduledAt ?? date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $_SESSION['user']['id'] ?? null,
        ];

        return $this->db->insert('email_queue', $queueData);
    }

    /**
     * Check if daily limit allows more emails
     */
    private function checkDailyLimit(array $settings): bool
    {
        $today = date('Y-m-d');

        // Reset counter if new day
        if ($settings['last_reset_date'] !== $today) {
            $this->db->update('email_settings', [
                'emails_sent_today' => 0,
                'last_reset_date' => $today,
            ], ['id' => $settings['id']]);
            return true;
        }

        return $settings['emails_sent_today'] < $settings['daily_limit'];
    }

    /**
     * Increment daily email count
     */
    private function incrementDailyCount(int $tenantId): void
    {
        $this->db->query(
            "UPDATE email_settings SET emails_sent_today = emails_sent_today + 1 WHERE tenant_id = ?",
            [$tenantId]
        );
    }

    /**
     * Log email to database
     */
    private function logEmail(array $email, string $status, ?string $messageId = null, ?string $error = null): void
    {
        $tenantId = $this->db->getTenantId();

        // Log for each recipient
        $recipients = is_array($email['to']) ? $email['to'] : [['email' => $email['to']]];

        foreach ($recipients as $recipient) {
            $this->db->insert('email_logs', [
                'tenant_id' => $tenantId,
                'queue_id' => $email['queue_id'] ?? null,
                'message_id' => $messageId,
                'to_email' => is_array($recipient) ? $recipient['email'] : $recipient,
                'to_name' => is_array($recipient) ? ($recipient['name'] ?? null) : null,
                'subject' => $email['subject'] ?? '',
                'context_type' => $email['context_type'] ?? null,
                'context_id' => $email['context_id'] ?? null,
                'status' => $status,
                'error_message' => $error,
                'sent_at' => $status === 'sent' ? date('Y-m-d H:i:s') : null,
                'created_at' => date('Y-m-d H:i:s'),
            ]);
        }
    }

    /**
     * Send email via SMTP
     */
    private function sendViaSMTP(array $settings, string $password, array $email): array
    {
        $host = $settings['smtp_host'];
        $port = (int) $settings['smtp_port'];
        $encryption = $settings['encryption'];
        $username = $settings['username'];
        $senderEmail = $settings['sender_email'];
        $senderName = $settings['sender_name'];

        // Build connection string
        $prefix = '';
        if ($encryption === 'ssl') {
            $prefix = 'ssl://';
        } elseif ($encryption === 'tls') {
            $prefix = 'tls://';
        }

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ]
        ]);

        $socket = @stream_socket_client(
            $prefix . $host . ':' . $port,
            $errno,
            $errstr,
            30,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$socket) {
            return ['success' => false, 'error' => "Connection failed: $errstr"];
        }

        // Read greeting
        $this->readSmtpResponse($socket);

        // EHLO
        fwrite($socket, "EHLO " . gethostname() . "\r\n");
        $this->readSmtpResponse($socket);

        // STARTTLS if needed
        if ($encryption === 'starttls') {
            fwrite($socket, "STARTTLS\r\n");
            $response = $this->readSmtpResponse($socket);
            if (strpos($response, '220') === 0) {
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                fwrite($socket, "EHLO " . gethostname() . "\r\n");
                $this->readSmtpResponse($socket);
            }
        }

        // AUTH
        fwrite($socket, "AUTH LOGIN\r\n");
        $this->readSmtpResponse($socket);
        fwrite($socket, base64_encode($username) . "\r\n");
        $this->readSmtpResponse($socket);
        fwrite($socket, base64_encode($password) . "\r\n");
        $response = $this->readSmtpResponse($socket);

        if (strpos($response, '235') !== 0) {
            fclose($socket);
            return ['success' => false, 'error' => 'Authentication failed'];
        }

        // MAIL FROM
        fwrite($socket, "MAIL FROM:<$senderEmail>\r\n");
        $this->readSmtpResponse($socket);

        // RCPT TO (all recipients)
        $recipients = is_array($email['to']) ? $email['to'] : [['email' => $email['to']]];
        foreach ($recipients as $recipient) {
            $toEmail = is_array($recipient) ? $recipient['email'] : $recipient;
            fwrite($socket, "RCPT TO:<$toEmail>\r\n");
            $this->readSmtpResponse($socket);
        }

        // CC
        if (!empty($email['cc'])) {
            foreach ($email['cc'] as $cc) {
                $ccEmail = is_array($cc) ? $cc['email'] : $cc;
                fwrite($socket, "RCPT TO:<$ccEmail>\r\n");
                $this->readSmtpResponse($socket);
            }
        }

        // BCC
        if (!empty($email['bcc'])) {
            foreach ($email['bcc'] as $bcc) {
                $bccEmail = is_array($bcc) ? $bcc['email'] : $bcc;
                fwrite($socket, "RCPT TO:<$bccEmail>\r\n");
                $this->readSmtpResponse($socket);
            }
        }

        // DATA
        fwrite($socket, "DATA\r\n");
        $this->readSmtpResponse($socket);

        // Build message
        $messageId = '<' . uniqid('bf_', true) . '@' . parse_url($host, PHP_URL_HOST) . '>';
        $boundary = '----=_Part_' . md5(uniqid());

        $headers = [];
        $headers[] = "Message-ID: $messageId";
        $headers[] = "Date: " . date('r');
        $headers[] = "From: " . ($senderName ? "\"$senderName\" <$senderEmail>" : $senderEmail);

        // Build To header
        $toHeader = [];
        foreach ($recipients as $recipient) {
            if (is_array($recipient)) {
                $toHeader[] = $recipient['name']
                    ? "\"{$recipient['name']}\" <{$recipient['email']}>"
                    : $recipient['email'];
            } else {
                $toHeader[] = $recipient;
            }
        }
        $headers[] = "To: " . implode(', ', $toHeader);

        // CC header
        if (!empty($email['cc'])) {
            $ccHeader = [];
            foreach ($email['cc'] as $cc) {
                $ccHeader[] = is_array($cc) ? $cc['email'] : $cc;
            }
            $headers[] = "Cc: " . implode(', ', $ccHeader);
        }

        if (!empty($settings['reply_to_email'])) {
            $headers[] = "Reply-To: {$settings['reply_to_email']}";
        }

        $headers[] = "Subject: " . $this->encodeSubject($email['subject'] ?? '');
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"$boundary\"";

        $message = implode("\r\n", $headers) . "\r\n\r\n";

        // Plain text part
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/plain; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $message .= quoted_printable_encode($email['body_plain'] ?? strip_tags($email['body_html'] ?? '')) . "\r\n\r\n";

        // HTML part
        $message .= "--$boundary\r\n";
        $message .= "Content-Type: text/html; charset=UTF-8\r\n";
        $message .= "Content-Transfer-Encoding: quoted-printable\r\n\r\n";
        $message .= quoted_printable_encode($email['body_html'] ?? '') . "\r\n\r\n";

        $message .= "--$boundary--\r\n";
        $message .= "\r\n.\r\n";

        fwrite($socket, $message);
        $response = $this->readSmtpResponse($socket);

        if (strpos($response, '250') !== 0) {
            fclose($socket);
            return ['success' => false, 'error' => "Send failed: $response"];
        }

        // QUIT
        fwrite($socket, "QUIT\r\n");
        fclose($socket);

        return ['success' => true, 'message_id' => $messageId];
    }

    /**
     * Encode subject for SMTP
     */
    private function encodeSubject(string $subject): string
    {
        if (preg_match('/[^\x20-\x7E]/', $subject)) {
            return '=?UTF-8?B?' . base64_encode($subject) . '?=';
        }
        return $subject;
    }

    /**
     * Encrypt password for storage
     */
    private function encryptPassword(string $password, int $tenantId): string
    {
        $key = hash('sha256', $this->encryptionKey . $tenantId, true);
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($password, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        return base64_encode($iv . $encrypted);
    }

    /**
     * Decrypt password from storage
     */
    private function decryptPassword(?string $encrypted, int $tenantId): string
    {
        if (empty($encrypted)) {
            return '';
        }

        $key = hash('sha256', $this->encryptionKey . $tenantId, true);
        $data = base64_decode($encrypted);
        $iv = substr($data, 0, 16);
        $ciphertext = substr($data, 16);
        return openssl_decrypt($ciphertext, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
    }

    /**
     * Get email logs for current tenant
     */
    public function getLogs(int $page = 1, int $perPage = 25, ?string $contextType = null, ?int $contextId = null): array
    {
        $tenantId = $this->db->getTenantId();
        $offset = ($page - 1) * $perPage;

        $where = "tenant_id = ?";
        $params = [$tenantId];

        if ($contextType) {
            $where .= " AND context_type = ?";
            $params[] = $contextType;
        }

        if ($contextId) {
            $where .= " AND context_id = ?";
            $params[] = $contextId;
        }

        $total = $this->db->fetch(
            "SELECT COUNT(*) as count FROM email_logs WHERE $where",
            $params
        )['count'];

        $logs = $this->db->fetchAll(
            "SELECT * FROM email_logs WHERE $where ORDER BY created_at DESC LIMIT $perPage OFFSET $offset",
            $params
        );

        return [
            'logs' => $logs,
            'pagination' => [
                'current_page' => $page,
                'per_page' => $perPage,
                'total' => (int) $total,
                'total_pages' => ceil($total / $perPage),
            ],
        ];
    }
}
